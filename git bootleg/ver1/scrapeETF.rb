require 'Nokogiri'
require 'open-uri'
require 'csv'
require 'openssl'
OpenSSL::SSL::VERIFY_PEER = OpenSSL::SSL::VERIFY_NONE

class ETF
  attr_reader :symbol, :names, :weights, :description#=, :shares
  
  def initialize(symbol)
    @description
    @symbol = symbol
    @names = []; @weights = []#; @shares = []
  end
  
  def get_data
    page_url = "https://www.spdrs.com/product/fund.seam?ticker=" + @symbol
    page = Nokogiri::HTML(open(page_url))
    indexer = 0
    print ", scraping page of type "
    
    if page.xpath('//div[contains(@class,\'sect fund_top_holdings\')]'\
                        '/table/tr/th').text.include? "Mkt Cap" #table with Mkt Cap
      type = 1
    elsif page.xpath('//div[contains(@class,\'sect fund_top_holdings\')]'\
                        '/table/tr/th').text.include? "Shares" #table with Shares
      type = 0
    elsif page.xpath('//div[contains(@class,\'sect fund_top_holdings\')]'\
                        '/table/tr/th').text.include? "ISIN" #table with ISIN
      type = 2
    else #table with underlying holdings
      type =3
    end
    print type
    xpath = '//div[contains(@class,\'sect fund_top_holdings\')]/table/tr/td' if type < 3
    xpath = '//div[contains(@class,\'sect fund_top_holdings underlying\')]/table/tr/td' if type == 3

      page.xpath('//div[contains(@class,\'sect fund_top_holdings\')]/table/tr/td').each do |tr|      
        wait = 0
        if type == 0
          @names.push(tr.text) if indexer == 0
          @weights.push(tr.text) if indexer == 1
          #@shares.push(tr.text) if indexer == 2
          indexer += 1
          indexer = 0 if indexer > 2
        elsif type == 1        
          @names.push(tr.text) if indexer == 0
          @weights.push(tr.text) if indexer == 1
          #@shares.push(tr.text) if indexer == 3
          indexer += 1
          indexer = 0 if indexer > 3
        elsif type == 2
          @names.push(tr.text) if indexer == 0
          @weights.push(tr.text) if indexer == 1
          indexer += 1
          indexer = 0 if indexer > 3
        elsif type == 3
          if wait > 2
            @names.push(tr.text) if indexer == 0
            @weights.push(tr.text) if indexer == 1
          end
          indexer += 1
          indexer = 0 if indexer > 1
          wait += 1
        end
      end
    @description = page.xpath('//div[contains(@class, \'objective\')]/p').text
  end
end

def get_names_tickers
  tickers = []
  hash = {}
  page_url = "https://www.spdrs.com/product/"
  page = Nokogiri::HTML(open(page_url))
  
  page.xpath('//span[contains(@id,\'performancePanel\')]'\
  '/div/table/tr/td').each do |tr| #[contains(@class, \'ticker\')]
    #names.push(tr.text.chomp) if (tr.to_s.include? 'name' and tr.text.include? 'ETF')
    tickers.push(tr.text.chomp) if (tr.to_s.include? "ticker" and tr.text.empty? == false) #parse all names and tickers to array
  end
  
  for i in 0..(tickers.size/2 - 1) do
    hash[tickers[i * 2 + 1]] = tickers[i * 2]
  end
  #puts names
  hash
end

hash = get_names_tickers

CSV.open("ETFs.csv", "wb") do |csv|
  csv << ["ETF Symbol", "Top 10 Holdings", "Weights"]
  hash.each do |key, value|
    print 'processing ' + key
    e = ETF.new(key)
    e.get_data
    
    puts ", writing"
    names = ""; weights = ""
    count = 1
    e.names.each do |name|
      names << count.to_s << ") " << name << 10.chr
      count += 1
    end
    count = 1
    e.weights.each do |weight|
      weights << count.to_s << ") " << weight << 10.chr
      count += 1
    end
    
    csv << [e.symbol, names, weights]
  end
end